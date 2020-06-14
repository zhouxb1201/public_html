<template>
  <Layout ref="load" class="verify-cardvoucher bg-f8">
    <Navbar/>
    <van-cell-group class="card-group-box" v-if="detail">
      <van-cell>
        <GoodsCard
          :title="detail.goods_name"
          :desc="detail.card_title"
          :thumb="detail.goods_picture"
        >
          <div slot="bottom" v-if="detail.state == 1">
            <div v-if="detail.type == 1">
              <span class="text-maintone">{{detail.invalid_time}}</span>到期
            </div>
            <div v-else>
              剩余
              <span class="text-maintone">{{detail.surplus_num}}</span>次
            </div>
          </div>
          <div slot="bottom" v-else>
            <span class="text-maintone">{{detail.state == 2?'已使用':'已过期'}}</span>
          </div>
        </GoodsCard>
      </van-cell>
      <van-cell v-if="detail.state == 1">
        <van-row type="flex" justify="end" class="btn-group">
          <van-button class="btn" size="small" :disabled="isDisabled" @click="onVerify">核销</van-button>
        </van-row>
      </van-cell>
    </van-cell-group>
    <Empty page-type="order" message="没有相关卡券" :show-foot="false" v-else/>
  </Layout>
</template>

<script>
import GoodsCard from "@/components/GoodsCard";
import Empty from "@/components/Empty";
import { GET_CODECARD, VERIFY_CARD } from "@/api/verify";
export default {
  name: "verify-cardvoucher",
  data() {
    return {
      detail: ""
    };
  },
  computed: {
    isDisabled() {
      return parseInt(this.detail.surplus_num) == 0 ? true : false;
    }
  },
  created() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      const code = $this.$route.params.code;
      GET_CODECARD(code)
        .then(res => {
          if (res.code == 1) {
            $this.detail = res.data;
            $this.$refs.load.success();
          } else {
            $this.$refs.load.result();
            $this.$Dialog
              .alert({ showCancelButton: true, message: res.data.prompt })
              .then(() => {
                $this.$store
                  .dispatch("selectStore", res.data.store_id)
                  .then(() => {
                    $this.loadData();
                  });
              })
              .catch(() => {
                $this.$router.back();
              });
          }
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    onVerify() {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "确定核销吗？"
        })
        .then(() => {
          VERIFY_CARD($this.$route.params.code).then(res => {
            $this.$Toast.success("核销成功");
            setTimeout(() => {
              $this.loadData();
            }, 500);
          });
        });
    }
  },
  components: {
    GoodsCard,
    Empty
  }
};
</script>
