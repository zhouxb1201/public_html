<template>
  <Layout ref="load" class="verify-gift bg-f8">
    <Navbar/>
    <van-cell-group class="card-group-box" v-if="detail">
      <van-cell>
        <GoodsCard :title="detail.gift_name" :thumb="detail.pic_cover_mid">
          <div slot="bottom" v-if="detail.state != 1" class="text-maintone">{{detail.state_name}}</div>
        </GoodsCard>
      </van-cell>
      <van-cell>
        <van-row type="flex" justify="end" class="btn-group">
          <van-button class="btn" size="small" :disabled="isDisabled" @click="onVerify">兑换</van-button>
        </van-row>
      </van-cell>
    </van-cell-group>
    <Empty page-type="order" message="没有相关礼品" :show-foot="false" v-else/>
  </Layout>
</template>

<script>
import GoodsCard from "@/components/GoodsCard";
import Empty from "@/components/Empty";
import { GET_CODEGIFT, VERIFY_GIFT } from "@/api/verify";
export default {
  name: "verify-gift",
  data() {
    return {
      detail: ""
    };
  },
  computed: {
    isDisabled() {
      return this.detail.state == 1 ? false : true;
    }
  },
  created() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      const code = $this.$route.params.code;
      GET_CODEGIFT(code)
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
          message: "确定兑换吗？"
        })
        .then(() => {
          VERIFY_GIFT($this.$route.params.code).then(res => {
            $this.$Toast.success("兑换成功");
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
