<template>
  <van-cell-group>
    <van-cell title="核销记录"/>
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      @load="loadList"
    >
      <van-cell v-for="(item,index) in list" :key="index">
        <van-row type="flex" justify="space-between">
          <van-col span="20">
            <div class="name">{{item.store_name}}</div>
            <div class="fs-12 text-secondary">{{item.create_time | formatDate('s')}}</div>
          </van-col>
          <van-col span="4" class="text-right text-maintone">
            <div>{{item.num}}</div>
          </van-col>
        </van-row>
      </van-cell>
    </List>
  </van-cell-group>
</template>

<script>
import { GET_CONSUMERCARDLOG } from "@/api/consumercard";
import { list } from "@/mixins";
export default {
  data() {
    return {
      params: {
        card_id: this.card_id
      }
    };
  },
  mixins: [list],
  props: {
    card_id: [String, Number]
  },
  mounted() {
    this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_CONSUMERCARDLOG($this.params)
        .then(({ data }) => {
          let list = data.data;
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  }
};
</script>

