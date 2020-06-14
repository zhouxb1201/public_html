<template>
  <div class="channel-depot-list bg-f8">
    <Navbar />
    <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTab" />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell-group>
        <van-cell class="item" v-for="(item,index) in list" :key="index">
          <van-row type="flex">
            <van-col span="18">
              <GoodsCard
                class="goods-info"
                :thumb="item.pic_cover"
                :title="item.goods_name"
                :desc="item.sku_name"
                :num="item.stock"
                :id="item.goods_id"
              />
            </van-col>
            <van-col span="6" class="item-right">
              <router-link
                tag="div"
                class="item-right-btn e-handle"
                :to="'/channel/depot/detail/'+item.sku_id"
              >
                <van-icon name="description" class="icon" />
                <span>明细</span>
              </router-link>
              <router-link
                tag="div"
                class="item-right-btn e-handle"
                to="/channel/goods/purchase"
                v-if="tab_active == 1"
              >
                <van-icon name="bag-o" class="icon" />
                <span>补货</span>
              </router-link>
              <router-link
                tag="div"
                class="item-right-btn e-handle"
                :to="{name:'goods-share',params:{goodsid:item.goods_id},query:{channel_id:item.channel_id}}"
                v-else
              >
                <van-icon name="share" class="icon" />
                <span>分享</span>
              </router-link>
            </van-col>
          </van-row>
        </van-cell>
      </van-cell-group>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadTab from "@/components/HeadTab";
import GoodsCard from "@/components/GoodsCard";
import { GET_DEPOTLIST } from "@/api/channel";
import { list } from "@/mixins";
export default sfc({
  name: "channel-depot-list",
  data() {
    return {
      tab_active: 0,
      tabs: [
        {
          name: "出售中",
          type: 1
        },
        {
          name: "售罄",
          type: 2
        }
      ],
      params: {
        page_index: 1,
        stock_status: 1
      }
    };
  },
  mixins: [list],
  mounted() {
    this.loadList();
  },
  methods: {
    onTab(index) {
      const $this = this;
      const type = $this.tabs[index].type;
      $this.params.stock_status = type;
      $this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_DEPOTLIST($this.params)
        .then(({ data }) => {
          let list = data.data ? data.data : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    HeadTab,
    GoodsCard
  }
});
</script>
<style scoped>
.item .goods-info {
  background: #fff;
  padding: 0;
  padding-right: 15px;
  border-right: 1px solid #ddd;
  margin-right: 10px;
}

.item-right {
  display: flex;
  align-items: center;
}

.item-right .item-right-btn {
  flex: 1;
  display: flex;
  flex-flow: column;
  text-align: center;
  font-size: 12px;
  color: #666;
}

.item-right .item-right-btn .icon {
  font-size: 26px;
  padding: 8px 4px 6px 4px;
}
</style>

